import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router';

declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  sections;
  units;

  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getSections();
    this.getUnits();
  }

  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(response => {
      this.sections = response;
    });
  }

  getUnits() {
    this.service.get('store/location.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  saveRack(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('store/location.php?type=saveRack',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.router.navigate(['/store/rack']);
        alertify.success('Data saved successfully');
      } else {
        alertify.error('An error occred, please try again');
      }
    });
  }

}
