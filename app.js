var app = new Vue({
  el: "#root",
  data: function () {
    return {
      regulamin: false,
      newUser: {email: "", password: ""}, //parametry rejestracji
      loginUser: {email: "", password: ""}, //parametry logowania
      errorNewUser: [], // bledy rejestracja
      errorLoginUser: [], // bledy logowanie
      user: {id: 0,email: "", cash: 0}, //dane o userze
      errorLogin: false,

      transfer: {email:"",cash:0},
      errorTransfer: [], // bledy transfer
      successTransfer: false,

      sentTransfers: {email: "", cash: ""},
      receiveTransfers: {email: "", cash: ""},
    }
  },

  mounted: function () {
    this.checkLogin();
    this.get_history();
  },
  // components

  methods: {
    toFormData(data) {
      const form = new FormData()
      for ( const key in data ) {
        form.append(key, data[key]);
      }
      return form;
    },
    checkLogin() {
      axios.post("script.php?action=checkLogin").then(
        function (respose) {
          if (respose.data.userID > 0) {
            if (document.location.href.indexOf("start.html") == -1) { // index
              document.location.href = "start.html";
            } else {
              var userValue = respose.data.user;
              app.user.id = userValue[0];
              app.user.email = userValue[1];
              app.user.cash = userValue[2];
            }
          } else {
            if (document.location.href.indexOf("start.html") > 0) { // start
              document.location.href = "index.html";
            }
            console.log("Niezalogowany");
          }
        }
      );
    },
    registerUser() {
      app.errorNewUser.shift(); //czyszczenie tablicy
      if(app.regulamin == false) {
        app.errorNewUser.push("Zaakceptuj regulamin");
      } else {
        var formData = app.toFormData(app.newUser);
        axios.post("script.php?action=newUser", formData).then(
          function (respose){
            if (respose.data.registerSuccess == true) {
              app.checkLogin();
            }
            app.newUser = {email:"",password:""};
          }
        );
      }
    },
    loginUserForm() {
      var formData = app.toFormData(app.loginUser);
      axios.post("script.php?action=loginUser", formData).then(
        function (respose) {
          if (respose.data.loginSuccess == true) {
            app.checkLogin();
          } else if (respose.data.loginError == true) {
            app.errorLogin = true;
          }
          app.password = "";
        }
      );
    },
    logout() {
      axios.post("script.php?action=logout").then(
        function (respose) {
          if (respose.data.logout == true) {
            document.location.href = "index.html";
          }
        }
      );
    },
    send_transfer() {
      app.successTransfer = false;
      app.errorTransfer = [];
      var formData = app.toFormData(app.transfer);
      axios.post("script.php?action=new_transfer", formData).then(
        function (respose){
          if (respose.data.successTransfer == true) {
            app.successTransfer = true;
            app.user.cash -= app.transfer.cash;
            app.transfer = {email:"",password:""};
            app.get_history();
          } else {
            app.errorTransfer = respose.data.errorTransfer;
          }
        }
      );
    },
    add_money() {
      axios.post("script.php?action=add_money").then(
        function (respose) {
          if (respose.data.addMoney == true) {
            app.user.cash++;
          }
        }
      );
    },
    get_history() {
      axios.post("script.php?action=get_history").then(
        function (respose) {
          app.sentTransfers = respose.data.sentTransfers;
          app.receiveTransfers = respose.data.receiveTransfers;
        }
      );
    }
  }
});
