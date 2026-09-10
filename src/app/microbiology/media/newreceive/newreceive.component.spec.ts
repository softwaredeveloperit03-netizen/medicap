import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewreceiveComponent } from './newreceive.component';

describe('NewreceiveComponent', () => {
  let component: NewreceiveComponent;
  let fixture: ComponentFixture<NewreceiveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewreceiveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewreceiveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
