import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-location',
  templateUrl: './location.component.html',
  styleUrls: ['./location.component.css']
})
export class LocationComponent implements OnInit {
  locations;
  isNewLocation = false;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getLocations();
  }

  getLocations() {
    this.service.get('hrDepartment.php?type=getLocations')
    .subscribe(response => {
      this.locations = response;
    });
  }

  addLocation(locationForm) {
    this.isNewLocation = false;
    this.service.post('hrDepartment.php?type=addLocation', JSON.stringify(locationForm.value))
    .subscribe(response => {
      locationForm.reset();
      this.getLocations();
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }
}
