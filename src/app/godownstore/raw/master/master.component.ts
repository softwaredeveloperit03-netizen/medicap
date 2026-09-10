import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css']
})
export class MasterComponent implements OnInit {

  criterias;
  selectedResult=[];
  types;
  constructor(private service: DataAccessService, private router: Router) { }
 
  ngOnInit(): void {
    this.getState();
    this.getMaterialType();
    
  }

  

  getState(){
    this.service.get('master/critiera.php?type=getCritiera').subscribe(response => {
      this.criterias= response;
    })
  }
  
  getMaterialType(){
    this.service.get('master/materialtype.php?type=getMaterialtype').subscribe(response => {
      this.types= response;
    })
  }

 



  
 save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp=data.value;
    this.service.post('master/critiera.php?type=saveCritiera', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        this.getState();
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }


  
}
