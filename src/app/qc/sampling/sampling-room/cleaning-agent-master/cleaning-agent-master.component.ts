import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-cleaning-agent-master',
  templateUrl: './cleaning-agent-master.component.html',
  styleUrls: ['./cleaning-agent-master.component.css']
})
export class CleaningAgentMasterComponent implements OnInit {
  agents: any[] = [];
  loading = false;
  showForm = false;
  editId: number | null = null;

  agent_name = '';
  manufacturer = '';
  concentration = '';
  default_lot_no = '';
  default_expiry = '';
  storage_condition = '';
  remarks = '';
  status = 'Active';

  constructor(public service: DataAccessService) {}

  ngOnInit() {
    this.loadAgents();
  }

  loadAgents() {
    this.loading = true;
    this.service.get('qc/sampling/sampling_room.php?type=getCleaningAgentMaster').subscribe((res: any) => {
      this.agents = Array.isArray(res) ? res : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  resetForm() {
    this.editId = null;
    this.agent_name = '';
    this.manufacturer = '';
    this.concentration = '';
    this.default_lot_no = '';
    this.default_expiry = '';
    this.storage_condition = '';
    this.remarks = '';
    this.status = 'Active';
    this.showForm = false;
  }

  openNew() {
    this.resetForm();
    this.showForm = true;
  }

  editAgent(agent: any) {
    this.editId = agent.id;
    this.agent_name = agent.agent_name || '';
    this.manufacturer = agent.manufacturer || '';
    this.concentration = agent.concentration || '';
    this.default_lot_no = agent.default_lot_no || '';
    this.default_expiry = agent.default_expiry ? agent.default_expiry.substring(0, 10) : '';
    this.storage_condition = agent.storage_condition || '';
    this.remarks = agent.remarks || '';
    this.status = agent.status || 'Active';
    this.showForm = true;
  }

  save() {
    if (!this.agent_name.trim()) {
      alertify.error('Cleaning agent name is required.');
      return;
    }
    const payload: any = {
      agent_name: this.agent_name,
      manufacturer: this.manufacturer,
      concentration: this.concentration,
      default_lot_no: this.default_lot_no,
      default_expiry: this.default_expiry,
      storage_condition: this.storage_condition,
      remarks: this.remarks,
      entry_by: localStorage.getItem('emp_id'),
      status: this.status
    };
    const type = this.editId ? 'updateCleaningAgent' : 'saveCleaningAgent';
    if (this.editId) payload.id = this.editId;

    this.service.postJson('qc/sampling/sampling_room.php?type=' + type, JSON.stringify(payload))
      .subscribe((res: any) => {
        if (res.status === 'success') {
          alertify.success(this.editId ? 'Cleaning agent updated.' : 'Cleaning agent saved.');
          this.resetForm();
          this.loadAgents();
        } else {
          alertify.error(res.status || 'Save failed');
        }
      }, () => alertify.error('Save failed'));
  }
}
