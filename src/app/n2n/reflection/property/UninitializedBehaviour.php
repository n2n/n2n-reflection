<?php
namespace n2n\reflection\property;

enum UninitializedBehaviour {
	case RETURN_NULL;
	case RETURN_UNDEFINED;
	case RETURN_UNDEFINED_IF_UNDEFINABLE;
	case THROW_EXCEPTION;
}