import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css'],
  providers: [DatePipe]
})
export class FormComponent implements OnInit {
  affecting_product = 'yes';
  affecting_equipment = 'no';
  today='';

  

  constructor(private service: DataAccessService, private router:Router,private datePipe:DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');

  }


  ngOnInit(): void {

  }

  saveUserForm(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qa/incident.php?type=saveIncident', JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success'){
        this.router.navigate(['/qms/incidents']);
        alertify.success('Record Inserted Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    })
  }
}

 