import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BulkDensityComponent } from './bulk-density.component';

describe('BulkDensityComponent', () => {
  let component: BulkDensityComponent;
  let fixture: ComponentFixture<BulkDensityComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BulkDensityComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BulkDensityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
