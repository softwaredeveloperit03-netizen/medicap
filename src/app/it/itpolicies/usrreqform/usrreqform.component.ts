import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-usrreqform',
  templateUrl: './usrreqform.component.html',
  styleUrls: ['./usrreqform.component.css'],

})
export class UsrreqformComponent implements OnInit {
  isNew = false;

  constructor(
    private service: DataAccessService
  ) {}

  ngOnInit(): void {}


}
