import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SolutionpreparationComponent } from './solutionpreparation.component';

describe('SolutionpreparationComponent', () => {
  let component: SolutionpreparationComponent;
  let fixture: ComponentFixture<SolutionpreparationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SolutionpreparationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SolutionpreparationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
