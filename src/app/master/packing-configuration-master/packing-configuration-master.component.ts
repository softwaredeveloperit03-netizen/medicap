import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

/** Default JSON shape applied to formulation product packing when a template is chosen */
export interface PackingConfigJson {
  configuration?: string;
  primary_packing?: string;
  primary_subtype?: string;
  ismono?: string;
  isouter?: string;
  unit?: string;
  primary_qty?: string;
  mono_qty?: string;
  outer_qty?: string;
  shipper_qty?: string;
  Combination?: string;
  pack_desc?: string;
}

@Component({
  selector: 'app-packing-configuration-master',
  templateUrl: './packing-configuration-master.component.html',
  styleUrls: ['./packing-configuration-master.component.css'],
})
export class PackingConfigurationMasterComponent implements OnInit {
  loading = false;
  saving = false;
  logList: any[] = [];
  searchQuery = '';
  showFormModal = false;
  showViewModal = false;
  viewMode = false;
  viewingId: number | null = null;

  /** Form model for new / view template */
  configuration_title = '';
  dosage_nature = '';
  combination_title = '';
  pattern_hint = '';
  primary_packing = '';
  primary_subtype = '';
  ismono = 'no';
  isouter = 'no';
  unit = '';
  primary_qty = '';
  mono_qty = '';
  outer_qty = '';
  shipper_qty = '';
  combination_pack = '';
  viewMeta: { entry_by?: string; entry_date?: string; is_active?: any } = {};

  readonly dosageNatureOptions = [
    { v: '', l: 'Select…' },
    { v: 'SOLID_ORAL', l: 'Solid oral (tablets, capsules, lozenges)' },
    { v: 'ORAL_LIQUID', l: 'Oral liquids (solutions, suspensions, syrups)' },
    { v: 'POWDER_ORAL', l: 'Oral powders & granules' },
    { v: 'SEMI_SOLID_TOPICAL', l: 'Semi-solids (cream, gel, ointment)' },
    { v: 'PARENTERAL', l: 'Parenteral (vials, ampoules, prefilled syringes)' },
    { v: 'LYOPHILIZED', l: 'Lyophilized / freeze-dried' },
    { v: 'INHALATION', l: 'Inhalation (MDI, DPI, nebuliser)' },
    { v: 'OPHTHALMIC', l: 'Ophthalmic' },
    { v: 'OTIC_NASAL', l: 'Otic / nasal sprays & drops' },
    { v: 'RECTAL_VAGINAL', l: 'Rectal / vaginal (suppository, pessary)' },
    { v: 'GASES', l: 'Medical gases' },
    { v: 'COMBINATION_PACK', l: 'Combination kit / co-pack' },
    { v: 'OTHER', l: 'Other / multi-presentations' },
  ];

  readonly primaryPackingPresets = [
    'Blister',
    'Strip',
    'Bottle',
    'Tube',
    'Vial',
    'Ampoule',
    'Syringe',
    'Sachet',
    'Pouch',
    'Pen device',
    'Inhaler device',
    'Dropper bottle',
    'Spray pump',
    'Jar',
    'Can',
    'Other',
  ];

  readonly patternGuide = `Suggested title pattern: «Primary» – «Secondary» – «Shipper» (e.g. Alu-Alu blister – PVC mono – shipper).
Map dosage: solids → blister/strip/bottle; liquids → bottle/vial; parenterals → vial/ampoule/syringe; topicals → tube/jar; inhalation → device + primary pack.`;

  plant_id: string;

  constructor(private service: DataAccessService) {
    this.plant_id = String(this.service.getPlantConfigFields('plant_id') || '');
  }

  ngOnInit(): void {
    this.loadLog();
  }

  loadLog(): void {
    this.loading = true;
    this.service
      .get(
        'master/packing_config_master.php?type=listPackingConfigMaster&includeInactive=1&includeJson=1'
      )
      .subscribe({
        next: (res: any) => {
          this.loading = false;
          if (res && res.status === 'invalid_token') {
            this.logList = [];
            alertify.error('Session expired or invalid. Please log in again.');
            return;
          }
          if (res && res.status === 'error' && res.message) {
            this.logList = [];
            alertify.error('Could not load log: ' + res.message);
            return;
          }
          this.logList = Array.isArray(res) ? res : [];
        },
        error: () => {
          this.logList = [];
          this.loading = false;
          alertify.error('Could not load packing configuration log (network or server error).');
        },
      });
  }

  openNew(): void {
    this.viewMode = false;
    this.viewingId = null;
    this.viewMeta = {};
    this.resetForm();
    this.showFormModal = true;
  }

  closeForm(): void {
    this.showFormModal = false;
    this.viewMode = false;
    this.viewingId = null;
  }

  closeView(): void {
    this.showViewModal = false;
    this.viewMode = false;
    this.viewingId = null;
    this.resetForm();
  }

  openView(row: any): void {
    if (!row?.id) {
      return;
    }
    this.viewMode = true;
    this.viewingId = Number(row.id);
    this.resetForm();
    this.service
      .get(
        'master/packing_config_master.php?type=getPackingConfigMasterById&id=' +
          encodeURIComponent(String(row.id))
      )
      .subscribe({
        next: (res: any) => {
          if (!res || !res.id) {
            // Fallback to list row if by-id empty
            this.fillFormFromRow(row);
          } else {
            this.fillFormFromRow(res);
          }
          this.showViewModal = true;
        },
        error: () => {
          this.fillFormFromRow(row);
          this.showViewModal = true;
        },
      });
  }

  private fillFormFromRow(row: any): void {
    if (!row) {
      return;
    }
    let cfg: any = row.config;
    if (!cfg && typeof row.configuration_json === 'string' && row.configuration_json.trim()) {
      try {
        cfg = JSON.parse(row.configuration_json);
      } catch {
        cfg = {};
      }
    }
    if (!cfg || typeof cfg !== 'object') {
      cfg = {};
    }
    this.configuration_title =
      String(cfg.configuration || row.configuration_title || '').trim();
    this.dosage_nature = String(row.dosage_nature || '');
    this.combination_title = String(row.combination_title || '');
    this.pattern_hint = String(cfg.pack_desc || row.pattern_hint || '');
    this.primary_packing = String(cfg.primary_packing || '');
    this.primary_subtype = String(cfg.primary_subtype || '');
    this.ismono = String(cfg.ismono || 'no').toLowerCase() === 'yes' ? 'yes' : 'no';
    this.isouter = String(cfg.isouter || 'no').toLowerCase() === 'yes' ? 'yes' : 'no';
    this.unit = String(cfg.unit || '');
    this.primary_qty = String(cfg.primary_qty ?? '');
    this.mono_qty = String(cfg.mono_qty ?? '');
    this.outer_qty = String(cfg.outer_qty ?? '');
    this.shipper_qty = String(cfg.shipper_qty ?? '');
    this.combination_pack = String(cfg.Combination || row.combination_title || '');
    this.viewMeta = {
      entry_by: row.entry_by || '',
      entry_date: row.entry_date || '',
      is_active: row.is_active,
    };
  }

  resetForm(): void {
    this.configuration_title = '';
    this.dosage_nature = '';
    this.combination_title = '';
    this.pattern_hint = '';
    this.primary_packing = '';
    this.primary_subtype = '';
    this.ismono = 'no';
    this.isouter = 'no';
    this.unit = '';
    this.primary_qty = '';
    this.mono_qty = '';
    this.outer_qty = '';
    this.shipper_qty = '';
    this.combination_pack = '';
  }

  buildJsonPayload(): string {
    const o: PackingConfigJson = {
      configuration: this.configuration_title || '',
      primary_packing: this.primary_packing || '',
      primary_subtype: this.primary_subtype || '',
      ismono: this.ismono || 'no',
      isouter: this.isouter || 'no',
      unit: this.unit || '',
      primary_qty: this.primary_qty || '',
      mono_qty: this.mono_qty || '',
      outer_qty: this.outer_qty || '',
      shipper_qty: this.shipper_qty || '',
      Combination: this.combination_pack || '',
      pack_desc: this.pattern_hint || '',
    };
    return JSON.stringify(o);
  }

  saveTemplate(): void {
    const title = (this.configuration_title || '').trim();
    if (!title) {
      alertify.error('Configuration title is required.');
      return;
    }
    this.saving = true;
    const body = {
      configuration_title: title,
      dosage_nature: this.dosage_nature || '',
      combination_title: (this.combination_title || '').trim(),
      pattern_hint: (this.pattern_hint || '').trim(),
      configuration_json: this.buildJsonPayload(),
    };
    this.service
      .postJson('master/packing_config_master.php?type=savePackingConfigMaster', JSON.stringify(body))
      .subscribe({
        next: (res: any) => {
          this.saving = false;
          if (res && res.status === 'success') {
            alertify.success('Packing configuration saved.');
            this.closeForm();
            this.loadLog();
          } else {
            alertify.error((res && res.message) || res?.status || 'Save failed');
          }
        },
        error: () => {
          this.saving = false;
          alertify.error('Save failed');
        },
      });
  }

  deactivate(row: any): void {
    if (!row?.id) {
      return;
    }
    if (!confirm('Deactivate this configuration template?')) {
      return;
    }
    this.service.get(
      'master/packing_config_master.php?type=deactivatePackingConfigMaster&id=' + row.id
    )
      .subscribe({
        next: (res: any) => {
          if (res?.status === 'success') {
            alertify.success('Deactivated');
            this.loadLog();
          } else {
            alertify.error('Could not deactivate');
          }
        },
        error: () => alertify.error('Could not deactivate'),
      });
  }

  get filteredLog(): any[] {
    const q = (this.searchQuery || '').toLowerCase().trim();
    if (!q) {
      return this.logList;
    }
    return this.logList.filter(
      (r) =>
        String(r.configuration_title || '')
          .toLowerCase()
          .includes(q) ||
        String(r.dosage_nature || '')
          .toLowerCase()
          .includes(q) ||
        String(r.combination_title || '')
          .toLowerCase()
          .includes(q)
    );
  }

  dosageNatureLabel(code: string): string {
    const hit = this.dosageNatureOptions.find((o) => o.v === code);
    return hit ? hit.l : code || '—';
  }
}
