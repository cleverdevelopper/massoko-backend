<?php

namespace App\Http;

class Request
{
    private $httpMethod;
    private $uri;
    private $queryParams = [];
    private $postVars = [];
    private $headers = [];
    private $router;
    private $file;
    public $user; // Adicionado para suportar injeção de usuário pelo middleware sem avisos de depreciação

    public function __construct($router)
    {
        $this->router          = $router;
        $this->httpMethod      = $_SERVER['REQUEST_METHOD'] ?? '';
        $this->queryParams     = $_GET ?? [];
        $this->setUri();
        $this->setPostVars();
        $this->headers         = getallheaders();
    }

    private function setPostVars()
    {
        if ($this->httpMethod === 'GET') return;


        // ==========================================================
        // 1) Detectar Content-Type
        // ==========================================================
        
        $contentType = strtolower(
            $_SERVER['CONTENT_TYPE']
                ?? $_SERVER['HTTP_CONTENT_TYPE']
                ?? ($this->headers['Content-Type'] ?? '')
        );

        // ==========================================================
        // 2) Carregar POST normal ou JSON
        // ==========================================================
        if (strpos($contentType, 'application/json') !== false) {
            $this->postVars = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            $this->postVars = $_POST ?? [];
        }

        // ==========================================================
        // 3) Configurações específicas de uploads
        // ==========================================================
        $config = [
            'imagem' => ['multiple' => true, 'folder' => __DIR__ . '/../../images/'],
            'imagem-categoria' => ['multiple' => false, 'folder' => __DIR__ . '/../../images/categorias/'],
            'avatar' => ['multiple' => false, 'folder' => __DIR__ . '/../../images/avatars/'],
            'rating-images' => [
                'multiple' => true,
                'folder'   => __DIR__ . '/../../images/ratings/'
            ],
            'image_doc_frente' => [
                'multiple' => false,
                'folder'   => __DIR__ . '/../../images/documentos/'
            ],
            'image_doc_verso' => [
                'multiple' => false,
                'folder'   => __DIR__ . '/../../images/documentos/'
            ],
            'imagem_despacho' => [
                'multiple' => false,
                'folder'   => __DIR__ . '/../../images/comprovativos/'
            ]

        ];

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $uploaded = [];

        // ==========================================================
        // 4) Função recursiva para percorrer qualquer nível de $_FILES
        // ==========================================================
        $processFile = function ($field, $tmp, $name, $type, $cfg) use (&$uploaded, $allowed) {

            if (!is_string($tmp)) return; // NÃO é arquivo real
            if (!is_uploaded_file($tmp)) return;
            if (!in_array($type, $allowed)) return;

            $folder = $cfg['folder'];
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $newName = time() . '_' . uniqid() . '_' . basename($name);
            $dest    = $folder . $newName;

            if (move_uploaded_file($tmp, $dest)) {

                if ($cfg['multiple']) {
                    $uploaded[$field][] = $newName;
                } else {
                    $uploaded[$field] = $newName;
                }
            }
        };

        // ==========================================================
        // 5) Percorrer todos os campos configurados
        // ==========================================================
        foreach ($config as $field => $cfg) {

            if (!isset($_FILES[$field])) continue;

            $files = $_FILES[$field];

            //Função que percorre recursivamente os níveis
            $walk = function ($names, $tmps, $types, $cfg, $field) use (&$walk, $processFile) {

                foreach ($names as $key => $name) {

                    if (is_array($name)) {
                        // descer mais um nível
                        $walk(
                            $names[$key],
                            $tmps[$key],
                            $types[$key],
                            $cfg,
                            $field
                        );
                    } else {
                        // nível final → arquivo real
                        $tmp  = $tmps[$key];
                        $type = $types[$key];

                        $processFile($field, $tmp, $name, $type, $cfg);
                    }
                }
            };

            if (is_array($files['name'])) {
                // múltiplos arquivos
                $walk($files['name'], $files['tmp_name'], $files['type'], $cfg, $field);
            } else {
                // arquivo único
                $processFile($field, $files['tmp_name'], $files['name'], $files['type'], $cfg);
            }
        }

        // ==========================================================
        // 6) anexa ao POST final
        // ==========================================================
        if (!empty($uploaded)) {
            $this->postVars['uploaded_images'] = $uploaded;
        }
    }


    private function setUri()
    {
        $this->uri = $_SERVER['REQUEST_URI'] ?? '';
        $xURI = explode('?', $this->uri);
        $this->uri = $xURI[0];
    }

    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function getPostVars()
    {
        return $this->postVars;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function getFile()
    {
        return $this->file;
    }
}
