<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Account profile name and photo.
 */
class Profile extends MY_Controller
{
    private const PHOTO_URL_PREFIX = '/uploads/pwesto-profile/';
    private const PHOTO_TYPES = MY_Controller::IMAGE_UPLOAD;

    public function before_action()
    {
        parent::before_action();
        $this->call->model('UserModel');
    }

    /** POST /api/profile (multipart: name, optional picture) */
    public function update()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $this->validate_input($this->input(), [
            'name|Display name' => 'required|min_length[2]|max_length[80]',
        ], [
            'name' => [
                'required' => 'Enter a name between 2 and 80 characters.',
                'min_length' => 'Enter a name between 2 and 80 characters.',
                'max_length' => 'Enter a name between 2 and 80 characters.',
            ],
        ]);
        $name = $_POST['name'];

        $previousPicture = $user['picture'] ?? '';
        $picture = $previousPicture;
        // LavaLust Upload library: real MIME check, allow-list, 2 MB limit, random file name.
        $photo = $this->receive_upload('picture', self::PHOTO_TYPES, 2, $this->photo_directory());
        if ($photo !== null) {
            $picture = self::PHOTO_URL_PREFIX . $photo['filename'];
        }

        try {
            $updatedUser = $this->UserModel->update_profile($user['id'], $name, $picture);
        } catch (Throwable $error) {
            if ($picture !== $previousPicture) {
                $this->delete_photo($picture);
            }
            error_log('Pwesto profile update failed: ' . $error->getMessage());
            $this->fail('Your profile could not be updated. Please try again.', 503);
        }
        $this->session->set_userdata('pwesto_user', $updatedUser);
        if ($picture !== $previousPicture) {
            $this->delete_photo($previousPicture);
        }
        $this->respond(['user' => $updatedUser]);
    }

    private function photo_directory()
    {
        return ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pwesto-profile';
    }

    private function delete_photo($picture)
    {
        if (!is_string($picture) || strpos($picture, self::PHOTO_URL_PREFIX) !== 0) {
            return;
        }
        $fileName = substr($picture, strlen(self::PHOTO_URL_PREFIX));
        if (!preg_match('/^[a-f0-9]{32,40}\.(jpg|jpeg|png|webp)$/', $fileName)) {
            return;
        }
        $path = $this->photo_directory() . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($path) && !unlink($path)) {
            error_log('Pwesto old profile photo could not be removed: ' . $path);
        }
    }
}
