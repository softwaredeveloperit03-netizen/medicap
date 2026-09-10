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

  material_type = '';
  material_subtype='';
  name;
  grades;
  isNewForm = false;
  name1;
  materials;
  material_name;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getGrades();
  }
  getMaterial() {
    this.service.get('qc/standard.php?type=getPendingMaterials&material_type=' + this.material_type + '&material_subtype=' + this.material_subtype).subscribe(response => {
      this.materials = response;
    });
  }
  
  download() {
    this.service.open('qc/standard.php?type=downloadStandards')
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  checkType(value) {
    if (value == 'Raw Material') {
      this.material_subtype = 'API';
      this.getMaterial();
    } else if (value == 'Analytical Standard') {
      this.material_subtype = 'Chemical';
      this.getMaterial();
    }
  }

  saveStandardMaster(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }  
    this.service.post('qc/standard.php?type=saveStandard', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.isNewForm=false;
        this.router.navigate(['/master/standard'])
        alertify.success('Form has been saved successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }

}
