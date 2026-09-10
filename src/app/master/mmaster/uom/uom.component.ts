import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-uom',
  templateUrl: './uom.component.html',
  styleUrls: ['./uom.component.css']
})
export class UomComponent implements OnInit {

  constructor(public service: DataAccessService, private router: Router) {}

  units ;
  unit = '';
  ngOnInit(): void {
    this.getUnits();
  }

  getUnits() {
    this.units = []
    this.service.get('common.php?type=getUnits_List').subscribe(response => {
      this.units = response
    })
  }

  saveUnit(data) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    this.service.post('master/unit.php?type=saveUnit', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getUnits();
        alertify.success("Unit Saved Successfully");
      } else {
        alertify.error(response['status']);
      }
    }); 

  }













}
