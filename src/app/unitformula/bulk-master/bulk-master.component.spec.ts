import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BulkMasterComponent } from './bulk-master.component';

describe('BulkMasterComponent', () => {
  let component: BulkMasterComponent;
  let fixture: ComponentFixture<BulkMasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BulkMasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BulkMasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
