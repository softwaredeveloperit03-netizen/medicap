import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DisSolutionComponent } from './dis-solution.component';

describe('DisSolutionComponent', () => {
  let component: DisSolutionComponent;
  let fixture: ComponentFixture<DisSolutionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DisSolutionComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DisSolutionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
