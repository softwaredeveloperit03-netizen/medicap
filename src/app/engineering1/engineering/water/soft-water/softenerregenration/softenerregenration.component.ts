import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-softenerregenration',
  templateUrl: './softenerregenration.component.html',
  styleUrls: ['./softenerregenration.component.css']
})
export class SoftenerregenrationComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) {

   }

  ngOnInit(): void {
  }

  

}
