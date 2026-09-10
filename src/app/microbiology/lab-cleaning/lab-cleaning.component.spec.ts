import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LabCleaningComponent } from './lab-cleaning.component';

describe('LabCleaningComponent', () => {
  let component: LabCleaningComponent;
  let fixture: ComponentFixture<LabCleaningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LabCleaningComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LabCleaningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
