import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SpreceiveComponent } from './spreceive.component';

describe('SpreceiveComponent', () => {
  let component: SpreceiveComponent;
  let fixture: ComponentFixture<SpreceiveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SpreceiveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SpreceiveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
