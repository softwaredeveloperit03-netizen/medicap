import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dispensing',
  templateUrl: './dispensing.component.html',
  styleUrls: ['./dispensing.component.css']
})
export class DispensingComponent implements OnInit {

  isView = false;
  details;

  selectedResulted = [];
  constructor(private route:ActivatedRoute,private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.route.paramMap.subscribe(params => {
      this.getDispensingDetails(params.get('id'));
    });
  }

  getDispensingDetails(id) {
    this.service.get('production/dispensing.php?type=getDispensingDetails&id=' + id).subscribe(response => {
      this.details = response;
      this.isView = true;
    });
  }

  saveStage(){
    this.service.get('production/dispensing.php?type=requisitionRequest&id=' + this.details['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Material Requistion request has been send to store department');
        this.isView = false;
        this.router.navigate(['/batch/']);
      } else {
        alert(response['status']);
      }
    });
  }

}
