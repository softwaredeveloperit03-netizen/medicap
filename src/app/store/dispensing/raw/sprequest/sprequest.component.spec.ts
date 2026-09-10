import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SprequestComponent } from './sprequest.component';

describe('SprequestComponent', () => {
  let component: SprequestComponent;
  let fixture: ComponentFixture<SprequestComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SprequestComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SprequestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
