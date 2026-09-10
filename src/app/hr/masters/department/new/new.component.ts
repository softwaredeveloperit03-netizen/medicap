import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService, private router :Router) {}

  ngOnInit() {
  }

  saveDepartment(data) {
    if(!data.valid){
      alert('All feilds are Required');
    }
     this.service.post('hr/department.php?type=saveDepartment',JSON.stringify(data.value)).subscribe(response=>{
       if(response['status']=='success'){
          data.resetForm();
          this.router.navigate(['/department']);
          alert('Record Inserted Successfuly');
       }else{
         alert('Failed! some error occured')
       }
     })
  }

}
