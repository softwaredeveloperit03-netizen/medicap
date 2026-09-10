import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify
@Component({
  selector: 'app-new',
  imports: [FormsModule],
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
flag=false

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {

  }

  save(data){
    // if(!data.valid){
    //   alertify.error('All Field are required');
    //   return;
   
    let temp=data.value;
    this.service.post('master/frequency.php?type=saveFrequency',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        alertify.success("Record Inserted Succesfully");
        data.resetForm();
        this.router.navigate(['/qa/controlsample/masters/frequency']);
      } else {
        alertify.error("Failed: an error occured");
      }
    });
  }

  numberValidation(input)
  {
   let  newValue;
   newValue = input.value.replace(/[a-zA-Z]/g, '');
   if(newValue==input.value)
    this.flag=false
  else
   this.flag=true
  }

}
