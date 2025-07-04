<?php

namespace Response\Render;

use Database\DataAccess\DAOFactory;
use Helpers\Authenticate;
use Response\HTTPRenderer;

class HTMLRenderer implements HTTPRenderer {

    private string $viewFile;
    private array $data;

    public function __construct(string $viewFile, array $data = []) {
        $this->viewFile = $viewFile;
        $this->data = $data;
    }

    public function getFields(): array {
        return [
            "Content-Type" => "text/html; charset=UTF-8",
        ];
    }

    public function getContent(): string {
        $viewPath =$this->getViewPath($this->viewFile);

        if (!file_exists($viewPath)) {
            throw new \Exception("View file {$viewPath} does not exists.");
        }
        
        ob_start();
        extract($this->data);
        require $viewPath;
        return $this->getHeader() . ob_get_clean() . $this->getFooter();
    }

    private function getHeader(): string {
        ob_start();
        $user = Authenticate::getAuthenticatedUser();

        $notificationCount = 0;
        if ($user !== null) {
            $notificationCount = DAOFactory::getNotificationDAO()->getUserUnreadNotificationCount($user->getUserId());
        }

        require $this->getViewPath("layout/header");
        require $this->getViewPath("component/post_modal");
        require $this->getViewPath("component/reply_modal");
        require $this->getViewPath("component/message-boxes");
        require $this->getViewPath("component/sidebar");
        return ob_get_clean();
    }

    private function getFooter(): string {
        ob_start();
        $user = Authenticate::getAuthenticatedUser();
        require $this->getViewPath("layout/footer");
        return ob_get_clean();
    }

    private function getViewPath(string $viewFile): string {
        return sprintf("%s/%s/Views/%s.php",__DIR__, "../..", $viewFile);
    }

}
