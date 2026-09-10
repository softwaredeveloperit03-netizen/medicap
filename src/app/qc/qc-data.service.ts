
import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { DataAccessService } from '../data-access.service';

@Injectable({
  providedIn: 'root'
})
export class QcDataService {

  plants: any = [];
  observablePlant;
  constructor(private service: DataAccessService, private http: HttpClient) {
    this.observablePlant = new BehaviorSubject(this.plants);

    this.getPlants();
  }

  plantChange() {
    this.observablePlant.next(this.plants);
  }

  getPlants() {
    let url = this.service.url + 'common.php?type=getCompanyUnits' + '&token=' + localStorage.getItem('token') + '&user_no=gmpdemo1';
    this.http.get(url).subscribe(response => {
      this.plants = response;
      this.plantChange();
      // resolve(this.plants)
    });
  }
}
