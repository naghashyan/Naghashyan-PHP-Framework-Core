let NgsFormValidator = function (formElement, options) {
  const defaults = {
    showError: imValidator.showError,
    hideError: imValidator.hideError
  };
  options = Object.assign(defaults, options);
  let passwordFieald = false;
  let notRequiredPasswordFieald = false;
  let emailReFieald = false;
  let formValidateStatus = true;
  formElement.querySelectorAll('input, select').forEach(function (item) {
    let status = true;
    if (item.getAttribute('data-ngs-validate')) {
      let validateType = item.getAttribute("data-ngs-validate");
      let validateLength = item.getAttribute("data-ngs-length");
      let translationError = item.getAttribute("data-ngs-translation-error");

      if (!validateLength) {
        validateLength = 4;
      }


      if (item.tagName !== 'SELECT') {
        item.value = item.value.trim();
      }

      switch (validateType) {
        case "number":
          status = imValidator.validateNumber(item.value);
          break;
        case "float-number":
          status = imValidator.validateFloatNumber(item.value);
          break;
        case "string":
          status = imValidator.validateString(item.value, 1, false);
          break;
        case "text":
          status = imValidator.validateText(item.value);
          break;
        case "email":
          status = imValidator.validateEmail(item.value);
          if (emailReFieald && status) {
            status = imValidator.validateEmail(item.value, emailReFieald.val());
          }
          emailReFieald = item;
          break;
        case "username":
          status = imValidator.validateString(item.value, validateLength, true);
          break;
        case "username-email":
          status = imValidator.validateString(item.value, validateLength, true, true);
          break;
        case "password":
          status = imValidator.validateString(item.value, validateLength, false);
          if (passwordFieald && status === true) {
            status = imValidator.validatePasswords(item.value, passwordFieald.val());
          }
          passwordFieald = item;
          break;
        case "not-required-password":
          if (notRequiredPasswordFieald) {
            status = imValidator.validatePasswords(item.value, notRequiredPasswordFieald.val());
          }
          notRequiredPasswordFieald = item;
          break;
        case "mobile-number":
          status = imValidator.validateMobileNumber(item.value);
          break;
        case "policy":
          status = imValidator.validatePolicy(item);
          break;
        case "cc_expiration_date":
          status = imValidator.validateCCExpirationDate(item.value);
          break;
        case "ccv":
          status = imValidator.validateCCV(item.value);
          break;
      }
      if (status !== true) {
        formValidateStatus = false;

        if (translationError) {
          status = translationError;
        }

        options.showError(item, status);
      } else {
        options.hideError(item);
      }
    }
  });
  return formValidateStatus;
};
let imValidator = {

  validateEmail: function (str, str1) {
    let filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if (!filter.test(str)) {
      return "Please enter valid email";
    }
    if (str1) {
      str1 = str1.trim();
      if (str !== str1) {
        return "These emails don't match. Try again?";
      }
    }
    return true;
  },
  validateNumber: function (str) {
    let filter = /^[0-9]*$/;
    if (!filter.test(str)) {
      return "Please use only numbers.";
    }
    if (!str) {
      return "You can't leave this empty.";
    }
    return true;
  },
  validateFloatNumber: function (str) {
    str = str.trim();
    const filter = /^-?\d*(\.\d+)?$/;
    if (!filter.test(str)) {
      return "Please use only numbers.";
    }
    if (!str) {
      return "You can't leave this empty.";
    }
    return true;
  },
  validateMobileNumber: function (str) {
    const filter = /^[0-9\+\.\-]*$/;
    if (!filter.test(str)) {
      return "Please use only numbers.";
    }
    if (!str) {
      return "You can't leave this empty.";
    }
    let str1 = str.replace(/\-/g, "");
    let str2 = str1.replace(/\./g, "");
    if (str2.length !== 10 && str2.length !== 11) {
      return "invalid phone number";
    }
    return true;
  },
  validateString: function (str, len, allowChars, email) {
    if (!str) {
      return "You can't leave this empty.";
    }
    if (len) {
      if (str.length < len || str.length > 60) {
        return "Please use between " + len + " and 60 characters.";
      }
    }
    if (allowChars) {
      let filter = /^[A-Za-z0-9\_\-\.\s]*$/;
      if (email) {
        filter = /^[A-Za-z0-9\_\-\.\@]*$/;
      }
      if (!filter.test(str)) {
        return "Please use only letters (a-z), numbers, and periods.";
      }
    }
    return true;
  },
  validateText: function (str) {
    if (!str) {
      return "You can't leave this empty.";
    }
    const filter = /^[/A-Za-z0-9\_\-\.\@\s\,\+\%\$\&]*$/;
    if (!filter.test(str)) {
      return "Please use only letters (a-z), numbers slashes, and periods.";
    }

    return true;
  },
  validateCCExpirationDate: function (str) {
    if (!str) {
      return "You can't leave this empty.";
    }
    const filter = /^\d{2}\/{0,1}\d{2}$/;
    if (!filter.test(str)) {
      return "Please use mm/yy format for date";
    }

    return true;
  },
  validateCCV: function (str) {
    if (!str) {
      return "Please enter your card's CCV";
    }
    const filter = /^\d{3,4}$/;
    if (!filter.test(str)) {
      return "Please enter correct CCV";
    }

    return true;
  },
  validatePasswords: function (str, str1) {
    str = str.trim();
    str1 = str1.trim();
    const filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if (str !== str1) {
      return "Your passwords don't match";
    }
    return true;
  },
  validatePolicy: function (elem) {
    if (!elem.is(':checked')) {
      return "In order to use our services, you must agree to our Terms of Use and Privacy Policy.";
    }
    return true;
  },
  showError: function (elem, msg) {
    this.hideError(elem);
    elem.parentNode.insertAdjacentHTML('beforeend', "<div class='ngs_validate'>" + msg + "</div>");
    let elemStyle = {
      borderColor: elem.style.borderColor,
      borderWidth: elem.style.borderWidth,
      borderStyle: elem.style.borderStyle
    };
    elem.setAttribute('data-im-style', JSON.stringify(elemStyle));
    elem.style.borderColor = "#FC5458";
    elem.style.borderWidth = "1px";
    elem.style.borderStyle = 'solid';
  },
  hideError: function (elem) {
    let errorElement = elem.parentNode.getElementsByClassName('ngs_validate');
    if (errorElement.length === 0) {
      return;

    }
    errorElement[0].remove();
    let elemStyle = JSON.parse(elem.getAttribute('data-im-style'));
    for (let key in elemStyle) {
      if (elemStyle.hasOwnProperty(key)) {
        elem.style[key] = elemStyle[key];
      }
    }
  }
};
export default NgsFormValidator;
