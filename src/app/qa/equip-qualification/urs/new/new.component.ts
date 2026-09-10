import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  vendors;
  isView = false;

  specs = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getVendors();
    this.getqualifiequip();
  }



  qualiequip;
  getVendors() {
    this.service.get('qa.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getqualifiequip() {
    this.service.get('qa/qualification.php?type=getqualifiequip').subscribe(response => {
      this.qualiequip = response;
    });
  }

  selectedResult =[];

  view(index){

    this.selectedResult = this.qualiequip[index];
    this.isView = true;
  }


  addSpec(data) {
    this.specs[this.specs.length] = data.value;
    data.resetForm();
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['specs'] = this.specs;
    this.service.post('qa/qualification.php?type=saveUserRequirementSpecification&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('User requirement specification has been saved successfully');
        this.specs = [];
        data.resetForm();
        this.isView = false;
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
