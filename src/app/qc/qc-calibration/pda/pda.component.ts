import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-pda',
  templateUrl: './pda.component.html',
  styleUrls: ['./pda.component.css']
})
export class PdaComponent implements OnInit {

  isNew= false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

}
