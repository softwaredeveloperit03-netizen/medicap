import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  materials;
  employees;
  units;

  constructor(private service: DataAccessService, private router: Router) {

  }

  ngOnInit() {
    this.getMaterials();
    this.getEmployees();
    this.getUnits();
  }

  getMaterials() {
    this.service.get('common.php?type=getPackingMaterials').subscribe(response => {
      this.materials = response;
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  saveForm(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.service.post('rnd/qa/controlsample.php?type=savePackingControlSample', JSON.stringify(data.value)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alertify.success( 'Saved Successfully');
        this.router.navigate(['qa/controlsample/packing']);
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

  getEmployees() {
    this.service.get('employee.php?type=getEmp').subscribe(response => {
      this.employees = response;
    });
  }

}
