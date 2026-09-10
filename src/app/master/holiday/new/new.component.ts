import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service: DataAccessService, private router:Router) { }

  ngOnInit(): void {
  }


  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }  
    this.service.post('master/holiday.php?type=saveHoliday', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
         this.router.navigate(['/master/holiday'])
        alertify.success('Form has been saved successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
}
