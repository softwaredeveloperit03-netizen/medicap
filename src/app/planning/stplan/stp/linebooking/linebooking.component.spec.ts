import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LinebookingComponent } from './linebooking.component';

describe('LinebookingComponent', () => {
  let component: LinebookingComponent;
  let fixture: ComponentFixture<LinebookingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LinebookingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LinebookingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
