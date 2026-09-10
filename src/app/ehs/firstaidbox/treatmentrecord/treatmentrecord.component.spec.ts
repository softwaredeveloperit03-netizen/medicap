import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TreatmentrecordComponent } from './treatmentrecord.component';

describe('TreatmentrecordComponent', () => {
  let component: TreatmentrecordComponent;
  let fixture: ComponentFixture<TreatmentrecordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TreatmentrecordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TreatmentrecordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
