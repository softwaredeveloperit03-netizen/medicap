import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-revise-checklist',
  templateUrl: './revise-checklist.component.html',
  styleUrls: ['./revise-checklist.component.css']
})
export class ReviseChecklistComponent implements OnInit {

  isView = false;
  result = [];
  constructor(private service: DataAccessService,private route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    let id = this.route.snapshot.paramMap.get('id');
    this.getChecklistRecord(id);
  }

  getChecklistRecord(id) {
    this.service.get('batch-release.php?type=getChecklistRecord&id=' + id).subscribe((response: any) => {
      this.result = response;
      this.isView = true;
    });
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let checkpoints = this.result['checkpoints'];
    checkpoints[checkpoints.length] = temp['checkpoint'];
    this.result['checkpoints'] = checkpoints;
    data.resetForm();
  }

  del(index) {
    let checkpoints = this.result['checkpoints'];
    checkpoints.splice(index, 1);
    this.result['checkpoints'] = checkpoints;
  }

  update() {
    let checkpoints = this.result['checkpoints'];
    if (checkpoints?.length == 0) {
      alert('Checkpints are required');
      return;
    }
    this.service.post('batch-release.php?type=reviseChecklist', JSON.stringify(this.result)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Checklist Master Updated Successfully');
        this.router.navigate(['/batchrelease/checklist-log']);
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
