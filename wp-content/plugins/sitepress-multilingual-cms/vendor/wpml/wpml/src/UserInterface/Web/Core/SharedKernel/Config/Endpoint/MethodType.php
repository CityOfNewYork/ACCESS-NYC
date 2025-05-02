<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint;

interface MethodType {
  const GET = 'GET';
  const POST = 'POST';
  const PUSH = 'PUSH';
  const PULL = 'PUT';
  const DELETE = 'DELETE';
}
