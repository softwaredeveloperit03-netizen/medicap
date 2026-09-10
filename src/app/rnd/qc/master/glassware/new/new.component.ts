import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
  }

  saveGlasswares(data) {
    this.service.post('rnd/qc/master/glassware.php?type=saveGlassware', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        data.reset();
        alertify.success('Form saved successfully.');
        this.router.navigate(['/qc/master/glassware']);
      }
    });
  }

}
