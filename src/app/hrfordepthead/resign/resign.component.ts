import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { ChangeDetectorRef } from '@angular/core';
@Component({
  selector: 'app-resign',
  templateUrl: './resign.component.html',
  styleUrls: ['./resign.component.css']
})
export class ResignComponent implements OnInit {
 
  resigacc= false;
  selectedResult= [];
  resignations;
 

  constructor(private service: DataAccessService, private router: Router, private cdr : ChangeDetectorRef) { }

  ngOnInit() {
    this.getData();
   }

  getData() {
    this.service.get('hr/resignation.php?type=get_resignation_by_deparetment&deptName='+localStorage.getItem('department')).subscribe(response => {
      this.resignations = response;
    });
  }

  
 
  resignationAcceptance(index) {
    this.selectedResult = this.resignations[index];
    this.submitResignation();
  }

 

  
  submitResignation( ) {
      
    let temp= {};
    this.service.post('hr/resignation.php?type=submitResignationReport&id='+this.selectedResult['id'], JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert("Data Submit Successfully !!");
        this.getData();
      } else {
        alert('An error occured');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

 
 


  





}
