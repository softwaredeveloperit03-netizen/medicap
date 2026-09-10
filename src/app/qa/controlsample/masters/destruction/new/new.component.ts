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

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    
  }

  


  save(data){
    if(!data.valid){
      alertify.error('All Field are required');
      return;
    }
    let temp=data.value;
    this.service.post('master/destruction.php?type=saveDestruction',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        alertify.success("Record Inserted Succesfully");
        data.resetForm();
        this.router.navigate(['/qa/controlsample/masters/destruction']);
      } else {
        alertify.error("Failed: an error occured");
      }
    });
  }


}
