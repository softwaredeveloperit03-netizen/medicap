import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-label',
  templateUrl: './label.component.html',
  styleUrls: ['./label.component.css']
})
export class LabelComponent implements OnInit {
 

  results: any[] = [];     
  allResults: any[] = [];  
  from_date: string = '';  
  to_date: string = '';   

  constructor(private service: DataAccessService, private router: Router) { }

  

  ngOnInit(): void {
  }

}
