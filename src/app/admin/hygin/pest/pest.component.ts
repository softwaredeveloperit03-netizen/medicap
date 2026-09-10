import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-pest',
  templateUrl: './pest.component.html',
  styleUrls: ['./pest.component.css']
})
export class PestComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }



  
  save(data) {

    let temp = data.value;
    

      this.service.post('admin/housekeeping.php?type=save_pest_record', JSON.stringify(temp)).subscribe(response => {
        alert("save successfully");
         data.reset();
       });
    

  }








}
