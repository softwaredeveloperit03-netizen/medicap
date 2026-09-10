import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-today',
  templateUrl: './today.component.html',
  styleUrls: ['./today.component.css']
})
export class TodayComponent implements OnInit {

  results;
  
  
  constructor(private service : DataAccessService,private router:Router) {
    }

  ngOnInit(): void {
  }
  
  download(){
    this.service.open('microbiology/fogging.php?type=downloadEnvironment')
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/fogging.php?type=saveFogging',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        alertify.success('Record Inserted successfully');
        this.router.navigate(['/microbiology/fogging'])
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  

}
