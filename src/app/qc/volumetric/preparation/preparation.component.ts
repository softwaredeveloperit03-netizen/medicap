import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-preparation',
  templateUrl: './preparation.component.html',
  styleUrls: ['./preparation.component.css']
})
export class PreparationComponent implements OnInit {

  isView = false;
  results;
   constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVolumetricPreparation();
      this.get_rights();
 
  }
  selectedResult =[];

  
 

  employee;

  getVolumetricPreparation() {
    this.service.get('qc/volumetric.php?type=getVolumetricPreparation').subscribe(response => {
      this.results = response;
    });
  }
  
   
  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }



  viewPhoto(url) {
    window.open(this.service.url + '../../upload/volumetric/' + this.selectedResult['weigh_slip']);
     window.open(url, '_blank');
  }

  
   
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  rights;


  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {      this.rights = response;
      this.isuser=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      this.dept_head=this.rights[0].dept_head
      this.isauditor=this.rights[0].isauditor
    });
  }


}





