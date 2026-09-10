import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewlogComponent } from './newlog.component';

describe('NewlogComponent', () => {
  let component: NewlogComponent;
  let fixture: ComponentFixture<NewlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
