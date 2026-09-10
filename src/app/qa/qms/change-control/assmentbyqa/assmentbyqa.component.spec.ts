import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AssmentbyqaComponent } from './assmentbyqa.component';

describe('AssmentbyqaComponent', () => {
  let component: AssmentbyqaComponent;
  let fixture: ComponentFixture<AssmentbyqaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AssmentbyqaComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AssmentbyqaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
