import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  
  results;
  isView=false;
  selectedResult = [];
  selectedmaterial=[];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingPO();
  }

  getPendingPO() {
    this.service.get('security/inword.php?type=getPendingPO').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.selectedmaterial = this.selectedResult['materials'];
    this.isView = true;
  }
  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('security/inword.php?type=saveChallan', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('PO save successfuly');
        data.resetForm();
        this.getPendingPO();
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  keyPressNumbers(event) {
    let number = this.selectedResult['driver_contact'];
    console.log(number);
    if (number !== undefined && number.length >= 10) {
      event.preventDefault();
      return false;
    }
    var charCode = (event.which) ? event.which : event.keyCode;
    // Only Numbers 0-9
    if ((charCode < 48 || charCode > 57)) {
      event.preventDefault();
      return false;
    } else {
      return true;
    }
  }

}
