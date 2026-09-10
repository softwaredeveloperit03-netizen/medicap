import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-bathroom',
  templateUrl: './bathroom.component.html',
  styleUrls: ['./bathroom.component.css']
})
export class BathroomComponent implements OnInit {

  frequency;
  time;
  date;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  savebath(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    } 
    let temp = Form.value;
    // temp['result']=this.result;
    this.service.post('admin/housekeeping.php?type=saveBath', JSON.stringify(temp))
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
