import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ActionreviewqaComponent } from './actionreviewqa.component';

describe('ActionreviewqaComponent', () => {
  let component: ActionreviewqaComponent;
  let fixture: ComponentFixture<ActionreviewqaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ActionreviewqaComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ActionreviewqaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
