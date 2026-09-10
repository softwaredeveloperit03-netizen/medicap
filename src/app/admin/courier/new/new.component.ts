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

date;
party_name;
courier_name;
docket_no;
receiver_name;
  ngOnInit(): void {
  }

  saveincoming(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp = Form.value;
    // temp['result']=this.result;
    this.service.post('hr/courier.php?type=saveIncoming', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
    }

}
