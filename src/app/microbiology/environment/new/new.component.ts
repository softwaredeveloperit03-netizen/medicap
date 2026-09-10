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

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
  }

  add(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    let temp=Form.value
    this.service.post('hr/achievement.php?type=saveEnvironment', JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success'){
        this.router.navigate(['/microbiology/environment']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }
}
