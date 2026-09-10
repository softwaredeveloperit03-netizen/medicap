import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  vendors;

  specs = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getVendors();
  }

  getVendors() {
    this.service.get('qa.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  addSpec(data) {
    this.specs[this.specs.length] = data.value;
    data.resetForm();
  }

  save(data) {
    let temp = data.value;
    temp['specs'] = this.specs;
    this.service.post('qa/qualification.php?type=saveUserRequirementSpecification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('User requirement specification has been saved successfully');
        this.specs = [];
        data.resetForm();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
