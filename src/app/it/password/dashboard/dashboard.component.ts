import { Component, OnInit } from '@angular/core';
import{DataAccessService} from 'src/app/data-access.service';
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
results;
loading;
  constructor(private service : DataAccessService) { }

  ngOnInit() {
    this.getRequets();
  }
  getRequets(){
    this.service.get('it/password.php?type=getRequests').subscribe(response =>{
      this.results = response;
    });
  }
  download()
{
  this.service.open('it/password.php?type=downloadRequests');
}
}
