<?php
// controllers/UserController.php
 
class UserController {
    private User $model;
 
    public function __construct() {
        require_once __DIR__ . '/../models/User.php';
        $this->model = new User();
    }
 
    public function handle(string $method, ?int $id, array $body): void {
        match ($method) {
            'GET'    => $id ? $this->show($id)  : $this->index(),
            'POST'   => $this->store($body),
            'PUT'    => $id ? $this->update($id, $body) : Response::error('ID mancante', 400),
            'PATCH'  => $id ? $this->patch($id, $body)  : Response::error('ID mancante', 400),
            'DELETE' => $id ? $this->destroy($id)       : Response::error('ID mancante', 400),
            default  => Response::error('Metodo non supportato', 405),
        };
    }
 
    // GET /api/utenti?page=1&per_page=10&cerca=mario&ruolo=admin
    private function index(): void {
        $result = $this->model->findAll($_GET);
        Response::paginate(
            $result['data'], $result['total'],
            $result['page'], $result['per_page']
        );
    }
 
    // GET /api/utenti/1
    private function show(int $id): void {
        $utente = $this->model->findById($id);
        $utente
            ? Response::success($utente)
            : Response::error('Utente non trovato', 404);
    }
 
    // POST /api/utenti
    private function store(array $body): void {
        $errors = $this->model->validate($body, true);
        if (!empty($errors)) {
            Response::error('Dati non validi', 422, $errors);
        }
        $id = $this->model->create($body);
        Response::success(['id' => $id], 'Utente creato con successo', 201);
    }
 
    // PUT /api/utenti/1
    private function update(int $id, array $body): void {
        if (!$this->model->findById($id)) Response::error('Utente non trovato', 404);
        $errors = $this->model->validate($body, false);
        if (!empty($errors)) Response::error('Dati non validi', 422, $errors);
        $this->model->update($id, $body);
        Response::success($this->model->findById($id), 'Utente aggiornato');
    }
 
    // PATCH /api/utenti/1
    private function patch(int $id, array $body): void {
        if (!$this->model->findById($id)) Response::error('Utente non trovato', 404);
        $errors = $this->model->validate($body, false);
        if (!empty($errors)) Response::error('Dati non validi', 422, $errors);
        $this->model->patch($id, $body);
        Response::success($this->model->findById($id), 'Utente aggiornato parzialmente');
    }
 
    // DELETE /api/utenti/1
    private function destroy(int $id): void {
        if (!$this->model->findById($id)) Response::error('Utente non trovato', 404);
        $this->model->delete($id);
        Response::success(null, 'Utente eliminato', 200);
    }
}
