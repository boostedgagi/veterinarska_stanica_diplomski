<?php

namespace App\Model;

class ContactMessage
{
    private string $name;

    private string $email;

    private string $content;

    /**
     * @param string $name
     * @param string $email
     * @param string $contact
     */
//    public function __construct(string $name, string $email, string $contact)
//    {
//        $this->name = $name;
//        $this->email = $email;
//        $this->contact = $contact;
//    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ContactMessage
     */
    public function setName(string $name): ContactMessage
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return ContactMessage
     */
    public function setEmail(string $email): ContactMessage
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return ContactMessage
     */
    public function setContent(string $content): ContactMessage
    {
        $this->content = $content;
        return $this;
    }
}