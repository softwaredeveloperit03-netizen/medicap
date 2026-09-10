import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RequirementlogComponent } from './requirementlog.component';

describe('RequirementlogComponent', () => {
  let component: RequirementlogComponent;
  let fixture: ComponentFixture<RequirementlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RequirementlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RequirementlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
