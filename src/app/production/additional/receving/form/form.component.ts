import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {

  results;
  selectedResult;
  isView= false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getData();
  }

  getData() {    
    this.service.get('production/additional_material.php?type=get_receving_materials').subscribe(response => {
      this.results = response;
    
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  accept() {
    if (this.selectedResult['material_type'] == '') {
      alertify.error('Select Type!');
      return;
    }
    if (this.selectedResult['ar_no'] == null) {
      alertify.error('Qty is Required');
      return;
    }
      this.service.post('production/additional_material.php?type=acceptreceving&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
