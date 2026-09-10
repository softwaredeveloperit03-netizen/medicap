import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-protocol',
  templateUrl: './protocol.component.html',
  styleUrls: ['./protocol.component.css']
})
export class ProtocolComponent implements OnInit {

  isNew= false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

}

