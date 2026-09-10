import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MediaPreComponent } from './media-pre.component';

describe('MediaPreComponent', () => {
  let component: MediaPreComponent;
  let fixture: ComponentFixture<MediaPreComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MediaPreComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MediaPreComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
