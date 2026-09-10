import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-view-specification',
  templateUrl: './view-specification.component.html',
  styleUrls: ['./view-specification.component.css']
})
export class ViewSpecificationComponent implements OnInit {

  selectedSpec = [];
  selectedSpecification = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
  }

}
