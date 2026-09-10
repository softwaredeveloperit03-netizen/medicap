import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WorkingStandardQualicicationComponent } from './working-standard-qualicication.component';

describe('WorkingStandardQualicicationComponent', () => {
  let component: WorkingStandardQualicicationComponent;
  let fixture: ComponentFixture<WorkingStandardQualicicationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WorkingStandardQualicicationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WorkingStandardQualicicationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
