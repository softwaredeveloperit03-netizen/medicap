import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PersonalhyComponent } from './personalhy.component';

describe('PersonalhyComponent', () => {
  let component: PersonalhyComponent;
  let fixture: ComponentFixture<PersonalhyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PersonalhyComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PersonalhyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
