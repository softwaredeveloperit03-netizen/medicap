import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router';
  declare let alertify;
@Component({
  selector: 'app-new-report',
  templateUrl: './new-report.component.html',
  styleUrls: ['./new-report.component.css']
})
export class NewReportComponent implements OnInit {
  sample_type='';
  sample = [
    {name: 'Particulate Air'},
    {name: 'Microbial Air'},
    {name: 'Surface Swab'},
    {name: 'Water'},
    {name: 'Surface Contact Plate'},
    {name: 'Disinfectant'},
    {name: 'Control sample'},
    {name: 'Settle Plate'},
    {name: 'Other'},
  ];
  grades;
  products;
  details='';
  materials;
  constructor(private service : DataAccessService,private router : Router) {
    
    }
  ngOnInit(): void {
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    });
  }

  getProductsByDosage(value) {
    this.service.get('production/master.php?type=getProductsByDosage&product_type=' + value).subscribe(response => {
      this.products = response;
    });
  }
  
  getApprovedRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_type='+ value).subscribe(response => {
      this.materials = response;
    });
  }
  saveReport(data){
    if(!data){
      alertify.error("All Fields are required");
      return;
    }
    this.service.post('microbiology/investigationreport.php?type=saveInvestigationSampling',JSON.stringify(data.value)).subscribe(response =>{
      if(response['status']=="success"){
      data.resetForm();
        this.router.navigate(['/microbiology/investigation']);
        alertify.success("Record Save Successfully !!");
      }else{
        alertify.error("Error To Insert Record !!!");
      }
    });
  }

}
